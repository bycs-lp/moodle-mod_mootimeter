@mod @mod_mootimeter @core_completion @javascript
Feature: Complete a mootimeter by answering
  In order to track the participation in a live session
  As a teacher
  I need the activity to be completed when participants answer

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
      | student1 | Student   | One      |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity   | name         | course | completion | completionanswerpages | completionanswerall |
      | mootimeter | Mootimeter 1 | C1     | 2          | 1                     | 1                   |
    And I am on the "Mootimeter 1" "mootimeter activity" page logged in as "teacher1"
    And I click on ".mootimeter-radio-card[data-name='wordcloud']" "css_element"
    And I set the field with xpath "//input[@aria-label='Question']" to "What do you like?"
    And I press the tab key
    And I click on "Click to show this page to participants." "button"
    And I log out

  Scenario: Answering all visible pages completes the activity
    Given I am on the "Course 1" course page logged in as "student1"
    And the "Answer pages: 1" completion condition of "Mootimeter 1" is displayed as "todo"
    And the "Answer all pages" completion condition of "Mootimeter 1" is displayed as "todo"
    When I am on the "Mootimeter 1" "mootimeter activity" page
    And I set the field with xpath "//input[contains(@class, 'mtmt-wc-answerinput')]" to "Moodle"
    And I press "Submit"
    And I should see "Moodle" in the "#mtmt_wordcloud_pills" "css_element"
    And I am on the "Course 1" course page
    Then the "Answer pages: 1" completion condition of "Mootimeter 1" is displayed as "done"
    And the "Answer all pages" completion condition of "Mootimeter 1" is displayed as "done"
