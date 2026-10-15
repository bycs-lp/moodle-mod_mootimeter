@mod @mod_mootimeter @mootimetertool_quiz @javascript
Feature: Run a quiz in a mootimeter
  In order to get live feedback
  As a teacher
  I need to create a quiz page that participants can answer

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

  Scenario: Teacher creates a quiz page and a student answers it
    Given I am on the "Mootimeter 1" "mootimeter activity" page logged in as "teacher1"
    And I should see "Please select the question type of a new question."
    And I click on ".mootimeter-radio-card[data-name='quiz']" "css_element"
    And I set the field with xpath "//input[@aria-label='Question']" to "How much is 2+2?"
    And I press the tab key
    And I set the field with xpath "(//input[@aria-label='Please enter an answer option.'])[1]" to "4"
    And I press the tab key
    And I set the field with xpath "(//input[@aria-label='Please enter an answer option.'])[2]" to "5"
    And I press the tab key
    And I click on "Click to show this page to participants." "button"
    And I click on "Click to allow students to view the results." "button"
    And I should see "How much is 2+2?" in the "#mootimeter-pagecontent" "css_element"
    And I log out
    When I am on the "Mootimeter 1" "mootimeter activity" page logged in as "student1"
    Then I should see "How much is 2+2?"
    And I should not see "Answer options"
    And I click on "//label[normalize-space(.)='4']" "xpath_element"
    And I press "Submit answer"
    And I should see "Answer stored successfully"
    And I click on "Show results page." "button"
    And "#mtmt_quiz_results_wrapper canvas" "css_element" should exist

  Scenario: Results are hidden until the teacher releases them
    Given I am on the "Mootimeter 1" "mootimeter activity" page logged in as "teacher1"
    And I click on ".mootimeter-radio-card[data-name='poll']" "css_element"
    And I set the field with xpath "//input[@aria-label='Question']" to "Which colour?"
    And I press the tab key
    And I click on "Click to show this page to participants." "button"
    And I log out
    When I am on the "Mootimeter 1" "mootimeter activity" page logged in as "student1"
    And I click on "Show results page." "button"
    Then I should see "The teacher must first allow the results to be displayed."
