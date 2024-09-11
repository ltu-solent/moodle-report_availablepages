@report @report_availablepages
Feature: Testing listlinks in report_availablepages
  In order to self-enrol on available courses
  As a user
  I should see a list of available courses

  Background:
    Given the following "users" exist:
    | username | firstname | lastname | email             |
    | user1    | User      | One      | user1@example.com |
    | user2    | User      | Two      | user2@example.com |
    And the following "categories" exist:
    | name         | category    | idnumber      | visible |
    | Courses      | 0           | SOL_Courses   | 1       |
    | Department 1 | SOL_Courses | dept1         | 1       |
    | Modules      | dept1       | dept1_modules | 1       |
    | Hidden       | 0           | archive       | 0       |
    And the following "cohorts" exist:
    | name    | idnumber |
    | Staff   | staff    |
    | Student | student  |

  Scenario Outline: Self enrolments options are displayed
    Given I log in as "admin"
    And the following "courses" exist:
    | fullname | shortname | visible   | startdate | enddate | category |
    | Course 1 | C1        | <visible> | <startdate> | <enddate> | <category> |
    And I add "Self enrolment" enrolment method in "Course 1" with:
     | Allow new self enrolments | <allownewenrolments> |
     | Only cohort members       | <cohort>             |
    And the following "cohort members" exist:
    | user  | cohort              |
    | user1 | <cohort_membership> |
    And I log in as "user1"
    When I visit "/report/availablepages"
    Then I <should_see> see "<categorybreadcrumb>"
    And I <should_see> see "Course 1"

    Examples:
    | visible | startdate       | enddate              | category      | allownewenrolments | cohort | cohort_membership | should_see | categorybreadcrumb               |
    | 1       | ##yesterday##   | ##Monday next week## | dept1_modules |                    | No     | staff             | should     | Courses / Department 1 / Modules |
    | 1       | ##yesterday##   | ##Monday next week## | dept1_modules |                    | staff  | staff             | should     | Courses / Department 1 / Modules |
    | 1       | ##yesterday##   | ##Monday next week## | dept1_modules |                    | staff  | student           | should not | Courses / Department 1 / Modules |
    | 1       | ##yesterday##   | ##Monday next week## | dept1_modules | 0                  | No     | student           | should not | Courses / Department 1 / Modules |
    # Don't allow on hidden courses or categories
    | 0       | ##yesterday##   | ##Monday next week## | dept1_modules |                    | No     | student           | should not | Courses / Department 1 / Modules |
    | 1       | ##yesterday##   | ##Monday next week## | archive       |                    | No     | student           | should not | Hidden                           |
    # Don't allow enrolments on completed courses
    | 1       | ##2 weeks ago## | ##1 week ago##       | dept1_modules |                    | No     | student           | should not | Courses / Department 1 / Modules |
    # Allow enrolments on future courses
    | 1       | ##Tomorrow##    | ##Monday next week## | dept1_modules |                    | No     | student           | should     | Courses / Department 1 / Modules |

  Scenario: Max enrolled reached, no more allowed.
    Given I log in as "admin"
    And the following "courses" exist:
    | fullname | shortname | category      |
    | Course 1 | C1        | dept1_modules |
    And I add "Self enrolment" enrolment method in "Course 1" with:
     | Max enrolled users | 1 |
    And I log in as "user1"
    And I am on "Course 1" course homepage
    And I press "Enrol me"
    Then I should see "Topic 1"
    And I log in as "user2"
    When I visit "/report/availablepages"
    Then I should not see "Courses / Department 1 / Modules"
    And I should not see "Course 1"
