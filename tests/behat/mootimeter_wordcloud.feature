@mod @mod_mootimeter @mootimetertool_wordcloud @javascript
Feature: Run a wordcloud in a mootimeter
  In order to collect terms live
  As a teacher
  I need to create a wordcloud page that participants can answer

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
      | student1 | Student   | One      |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity   | name         | course |
      | mootimeter | Mootimeter 1 | C1     |

  Scenario: Students submit words, the teacher sees them in the cloud and the overview
    Given I am on the "Mootimeter 1" "mootimeter activity" page logged in as "teacher1"
    And I click on ".mootimeter-radio-card[data-name='wordcloud']" "css_element"
    And I set the field with xpath "//input[@aria-label='Question']" to "What do you like?"
    And I press the tab key
    And I click on "Click to show this page to participants." "button"
    And I log out
    And I am on the "Mootimeter 1" "mootimeter activity" page logged in as "student1"
    And I should see "What do you like?"
    And I set the field with xpath "//input[contains(@class, 'mtmt-wc-answerinput')]" to "Moodle"
    And I press "Submit"
    And I should see "Moodle" in the "#mtmt_wordcloud_pills" "css_element"
    And I set the field with xpath "//input[contains(@class, 'mtmt-wc-answerinput')]" to "moodle"
    And I press "Submit"
    And I should see "Duplicate answers are not allowed"
    And I log out
    When I am on the "Mootimeter 1" "mootimeter activity" page logged in as "teacher1"
    And I click on "Show results page." "button"
    Then "//*[local-name()='svg']//*[local-name()='text'][contains(., 'Moodle')]" "xpath_element" should exist
    And I click on "Answer overview" "button"
    And I should see "Student One" in the "table" "css_element"
    And I should see "Moodle" in the "table" "css_element"
