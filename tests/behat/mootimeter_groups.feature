@mod @mod_mootimeter @javascript
Feature: Show the participation of a group in a mootimeter
  In order to follow the participation of my groups
  As a teacher
  I need to choose the group of the participation counter

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
      | student1 | Student   | One      |
      | student2 | Student   | Two      |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group A | C1     | GA       |
      | Group B | C1     | GB       |
    And the following "group members" exist:
      | user     | group |
      | student1 | GA    |
      | student2 | GB    |
    And the following "activities" exist:
      | activity   | name         | course | groupmode |
      | mootimeter | Mootimeter 1 | C1     | 1         |
    And I am on the "Mootimeter 1" "mootimeter activity" page logged in as "teacher1"
    And I click on ".mootimeter-radio-card[data-name='wordcloud']" "css_element"
    And I click on "Click to show this page to participants." "button"
    And I click on "Show the participation counter to participants" "text"

  Scenario: Teachers switch between all participants and the groups
    Given I should see "2 in the course"
    And "//select[@aria-label='Group']/option[.='All participants']" "xpath_element" should exist
    And "//select[@aria-label='Group']/option[.='Group B']" "xpath_element" should exist
    When I set the field with xpath "//select[@aria-label='Group']" to "Group A"
    Then I should see "1 in the group"

  Scenario: Participants in separate groups only see their own group
    Given I log out
    When I am on the "Mootimeter 1" "mootimeter activity" page logged in as "student1"
    Then I should see "1 in the group"
    And "//select[@aria-label='Group']" "xpath_element" should not exist
