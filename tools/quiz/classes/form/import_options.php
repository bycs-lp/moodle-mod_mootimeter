<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mootimetertool_quiz\form;

use core\context\user as context_user;
use core\output\html_writer;
use core\url;
use core_form\dynamic_form;
use mod_mootimeter\helper;
use mod_mootimeter\local\access;
use mootimetertool_quiz\quiz;

/**
 * Modal form to import answer options of a quiz or poll page from a CSV file.
 *
 * @package    mootimetertool_quiz
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_options extends dynamic_form {
    /** @var int Maximum size of the uploaded file in bytes. */
    const MAX_BYTES = 1048576;

    /** @var string Existing answer options are kept. */
    const MODE_APPEND = 'append';

    /** @var string Existing answer options and all answers are deleted. */
    const MODE_REPLACE = 'replace';

    /** @var access|null Access to the page. */
    private ?access $access = null;

    /**
     * Get the access to the page the options are imported to.
     *
     * @return access
     */
    private function get_access(): access {
        if (!$this->access) {
            $page = (new helper())->get_page($this->optional_param('pageid', 0, PARAM_INT));
            if (!$page || !(helper::get_toolhelper($page->tool) instanceof quiz)) {
                throw new \mod_mootimeter\exception\not_found_exception('pagenotfound', 'mod_mootimeter');
            }
            $this->access = access::for_page($page);
        }
        return $this->access;
    }

    /**
     * Get the tool of the page.
     *
     * @return quiz
     */
    private function get_tool(): quiz {
        return helper::get_toolhelper($this->get_access()->get_page()->tool);
    }

    #[\Override]
    protected function get_context_for_dynamic_submission(): \core\context {
        return $this->get_access()->context;
    }

    #[\Override]
    protected function check_access_for_dynamic_submission(): void {
        $this->get_access()->require_moderator();
    }

    #[\Override]
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'pageid');
        $mform->setType('pageid', PARAM_INT);

        $example = html_writer::link(
            new url('/mod/mootimeter/tools/quiz/example/answeroptions.csv'),
            get_string('import_options_example', 'mootimetertool_quiz'),
            ['download' => 'answeroptions.csv']
        );
        $mform->addElement('html', html_writer::div(
            html_writer::tag('p', get_string('import_options_description', 'mootimetertool_quiz', quiz::MAX_IMPORT_OPTIONS))
                . html_writer::tag('p', $example),
            'mtmt-import-options-description'
        ));

        $mform->addElement('filepicker', 'csvfile', get_string('import_options_file', 'mootimetertool_quiz'), null, [
            'accepted_types' => ['.csv', '.txt'],
            'maxbytes' => self::MAX_BYTES,
        ]);
        $mform->addRule('csvfile', null, 'required');

        if ($this->has_options()) {
            $mform->addElement(
                'radio',
                'mode',
                get_string('import_options_mode', 'mootimetertool_quiz'),
                get_string('import_options_append', 'mootimetertool_quiz'),
                self::MODE_APPEND
            );
            $mform->addElement(
                'radio',
                'mode',
                '',
                get_string('import_options_replace', 'mootimetertool_quiz'),
                self::MODE_REPLACE
            );
            $mform->setDefault('mode', self::MODE_APPEND);
        } else {
            $mform->addElement('hidden', 'mode', self::MODE_REPLACE);
        }
        $mform->setType('mode', PARAM_ALPHA);
    }

    /**
     * Whether the page has answer options somebody filled in.
     *
     * Options nobody has filled in yet, like the two empty ones of a new page, are replaced without asking.
     *
     * @return bool
     */
    private function has_options(): bool {
        foreach ($this->get_tool()->get_answer_options($this->get_access()->get_page()->id) as $option) {
            if (trim((string) $option->optiontext) !== '') {
                return true;
            }
        }
        return false;
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $options = self::parse_options($this->get_draft_content((int) ($data['csvfile'] ?? 0)));
        if (!$options) {
            $errors['csvfile'] = get_string('import_options_none', 'mootimetertool_quiz');
        } else if (count($options) > quiz::MAX_IMPORT_OPTIONS) {
            $errors['csvfile'] = get_string('import_options_toomany', 'mootimetertool_quiz', quiz::MAX_IMPORT_OPTIONS);
        }
        return $errors;
    }

    /**
     * Import the answer options.
     *
     * @return array the number of imported options
     */
    #[\Override]
    public function process_dynamic_submission() {
        $data = $this->get_data();
        $page = $this->get_access()->get_page();
        $tool = $this->get_tool();
        $texts = self::parse_options($this->get_draft_content((int) $data->csvfile));

        $items = [];
        if ($data->mode !== self::MODE_REPLACE) {
            foreach ($tool->get_answer_options($page->id) as $option) {
                $items[] = [
                    'id' => (int) $option->id,
                    'text' => $option->optiontext,
                    'isCorrect' => !empty($option->optioniscorrect),
                ];
            }
        }
        foreach ($texts as $text) {
            $items[] = ['text' => $text];
        }
        $tool->replace_options($page, $items);

        return ['count' => count($texts)];
    }

    #[\Override]
    public function set_data_for_dynamic_submission(): void {
        $this->set_data(['pageid' => $this->get_access()->get_page()->id]);
    }

    #[\Override]
    protected function get_page_url_for_dynamic_submission(): url {
        return new url('/mod/mootimeter/view.php', [
            'id' => $this->get_access()->cm->id,
            'pageid' => $this->get_access()->get_page()->id,
        ]);
    }

    /**
     * Get the content of the file in a draft area of the current user.
     *
     * @param int $draftitemid
     * @return string
     */
    private function get_draft_content(int $draftitemid): string {
        global $USER;
        $files = get_file_storage()->get_area_files(
            context_user::instance($USER->id)->id,
            'user',
            'draft',
            $draftitemid,
            'id DESC',
            false
        );
        return $files ? reset($files)->get_content() : '';
    }

    /**
     * Get the answer options of a CSV file: the first non-empty cell of each line.
     *
     * The delimiter (semicolon, tab or comma) is guessed from the first line.
     *
     * @param string $content
     * @return string[]
     */
    public static function parse_options(string $content): array {
        if (!mb_check_encoding($content, 'UTF-8')) {
            // Spreadsheet programs often save CSV files in the Windows encoding.
            $content = \core_text::convert($content, 'windows-1252', 'utf-8');
        }
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $firstline = strtok($content, "\r\n") ?: '';
        $delimiter = ',';
        $max = 0;
        foreach ([';', "\t", ','] as $candidate) {
            if (substr_count($firstline, $candidate) > $max) {
                $max = substr_count($firstline, $candidate);
                $delimiter = $candidate;
            }
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);
        $options = [];
        while (($row = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            foreach ($row as $cell) {
                if (trim((string) $cell) !== '') {
                    $options[] = trim($cell);
                    break;
                }
            }
        }
        fclose($handle);
        return $options;
    }
}
