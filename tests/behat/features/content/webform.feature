@api

Feature: Webforms
  In order to collect information about our users
  As a webform author
  I need to be able to create, manage and maintain forms.

  @webforms @webform_author
  Scenario: Create form access
    Given I am logged in as a user with the "Webform author" role
    When I follow "Structure"
    And I follow "Webforms"
    Then I should see the link "Add webform"

  @webforms @webform_author
  Scenario: Create a form
    Given I am logged in as a user with the "Webform author" role
    And I am on "/admin/structure/webform/add"
    When I fill in "Test webform" for "Title"
    And I fill in "Test administrative description" for "Administrative description"
    And I select "Other…" from "Category"
    And I fill in "Other category" for "Enter other…"
    And I select the radio button "Open"
    And I press "Save"
    Then I should be on "admin/structure/webform/add"
