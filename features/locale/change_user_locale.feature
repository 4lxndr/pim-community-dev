@javascript
Feature: Change user locale
  In order to change locale
  As an administrator
  I need to be able to change the locale of any user without reloading browser

  Background:
    Given the "apparel" catalog configuration
    And I am logged in as "Peter"

  Scenario: Successfully change my locale
    Given I am on the User profile edit page
    Then I visit the "Interfaces" tab
    And I fill in the following information:
     | UI locale | French (France) |
    And I save the user
    Then I should not see the text "There are unsaved changes."
    And I should see the text "Langue de l'interface"

  Scenario: Successfully change Mary's locale
    Given I edit the "mary" user
    Then I visit the "Interfaces" tab
    And I fill in the following information:
     | UI locale | French (France) |
    And I save the user
    Then I should not see the text "There are unsaved changes."
    Then I should not see the text "Collecter"

  Scenario: Should only see translated locales
    Given I am on the User profile edit page
    And I visit the "Interfaces" tab
    Then I should see English locale option
    And I should not see Breton locale option
