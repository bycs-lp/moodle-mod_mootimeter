@mod @mod_mootimeter @javascript
Feature: Control the submission of a mootimeter page
  In order to run a live session
  As a teacher
  I need to open and close the submission and to start a countdown

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
    And I am on the "Mootimeter 1" "mootimeter activity" page logged in as "teacher1"
    And I click on ".mootimeter-radio-card[data-name='wordcloud']" "css_element"
    And I set the field with xpath "//input[@aria-label='Question']" to "What do you like?"
    And I press the tab key
    And I click on "Click to show this page to participants." "button"

  Scenario: Participants can not answer while the submission is closed
    Given I click on "Close submission" "button"
    And I should see "The submission is closed."
    And I log out
    When I am on the "Mootimeter 1" "mootimeter activity" page logged in as "student1"
    Then I should see "The submission is closed."
    And "Submit" "button" should not exist
    And ".mootimeter-participation" "css_element" should not exist
    And I log out
    And I am on the "Mootimeter 1" "mootimeter activity" page logged in as "teacher1"
    And I click on "Open submission" "button"
    And I log out
    And I am on the "Mootimeter 1" "mootimeter activity" page logged in as "student1"
    And I should not see "The submission is closed."
    And "Submit" "button" should exist

  Scenario: The teacher starts and stops a countdown that participants see
    Given I click on "Countdown" "button"
    And I click on "5:00" "button"
    And ".mootimeter-countdown" "css_element" should exist
    And ".mootimeter-participation" "css_element" should exist
    And I log out
    And I am on the "Mootimeter 1" "mootimeter activity" page logged in as "student1"
    And ".mootimeter-countdown" "css_element" should exist
    And "Submit" "button" should exist
    And I log out
    When I am on the "Mootimeter 1" "mootimeter activity" page logged in as "teacher1"
    And I click on "Countdown" "button"
    And I click on "Stop countdown" "button"
    Then ".mootimeter-countdown" "css_element" should not exist

  Scenario: A countdown preset in the page settings starts when a participant opens the page
    Given I set the field "Countdown in seconds, started when the first participant opens the page (0 = none)" to "300"
    And I press the tab key
    And ".mootimeter-countdown" "css_element" should not exist
    And I log out
    When I am on the "Mootimeter 1" "mootimeter activity" page logged in as "student1"
    Then ".mootimeter-countdown" "css_element" should exist
    And I log out
    And I am on the "Mootimeter 1" "mootimeter activity" page logged in as "teacher1"
    And ".mootimeter-countdown" "css_element" should exist
    And I click on "Countdown" "button"
    And I click on "Stop countdown" "button"
    And I click on "Countdown" "button"
    And I should see "Start 5:00 (default)"

  Scenario: Participants see the participation counter once it is enabled
    Given I click on "Show the participation counter to participants" "text"
    And I log out
    When I am on the "Mootimeter 1" "mootimeter activity" page logged in as "student1"
    Then I should see "0 of 1 active participants answered"
    And I should see "1 in the course"

  Scenario: The results are published once the submission is closed
    Given I click on "Publish the results to participants once the submission is closed" "text"
    And I log out
    And I am on the "Mootimeter 1" "mootimeter activity" page logged in as "student1"
    And I set the field with xpath "//input[contains(@class, 'mtmt-wc-answerinput')]" to "Moodle"
    And I press "Submit"
    And I click on "Show results page." "button"
    And "//*[local-name()='svg']//*[local-name()='text'][contains(., 'Moodle')]" "xpath_element" should not exist
    And I log out
    And I am on the "Mootimeter 1" "mootimeter activity" page logged in as "teacher1"
    And I click on "Close submission" "button"
    And I log out
    When I am on the "Mootimeter 1" "mootimeter activity" page logged in as "student1"
    And I click on "Show results page." "button"
    Then "//*[local-name()='svg']//*[local-name()='text'][contains(., 'Moodle')]" "xpath_element" should exist
