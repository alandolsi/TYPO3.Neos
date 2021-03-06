Feature: Site management / List sites
  In order to manage sites
  As an administrator
  I need a way to list and manage sites

  Background:
    Given I imported the site "TYPO3.NeosDemoTypo3Org"
    And the following users exist:
      | username | password | firstname | lastname | roles         |
      | jdoe     | password | John      | Doe      | Administrator |
    And I am authenticated with "jdoe" and "password" for the backend

  @fixtures
  Scenario: List sites
    When I go to the "Administration / Site Management" module
    Then I should see the following sites in a table:
      | name                 |
      | TYPO3 Neos Demo Site |

  # Scenario: Add site from existing package

  @fixtures
  Scenario: Add site by creating a new package
    When I go to the "Administration / Site Management" module
    And I follow "Add new site"
    And I fill in "Package Key" with "Test.DemoSite"
    And I fill in "Site Name" with "Test Demo Site"
    And I press "Create"
    Then I should see the following sites in a table:
      | name                 |
      | Test Demo Site       |
      | TYPO3 Neos Demo Site |

  @fixtures
  Scenario: Update site name
    When I go to the "Administration / Site Management" module
    And I follow "Click to edit" for site "TYPO3 Neos Demo Site"
    And I fill in "Name" with "Updated Neos Demo Site"
    And I press "Save"
    Then I should see the following sites in a table:
      | name                   |
      | Updated Neos Demo Site |
