@api

Feature: Webforms
  In order to collect information about our users
  As a content editor
  I need to be able to create, manage and maintain forms.

  @webforms @content_editor
  Scenario: Create form access
    Given I am logged in as a user with the "Content editor" role
    When I follow "Structure"
    And I follow "Webforms"
    Then I should see the link "Add webform"

  @webforms @content_editor
  Scenario: Create a form
    Given I am logged in as a user with the "Content editor" role
    And I am on "/admin/structure/webform/add"
    When I fill in "Test webform" for "Title"
    And I fill in "Test administrative description" for "Administrative description"
    And I select "Other…" from "Category"
    And I fill in "Other category" for "Enter other…"
    And I select the radio button "Open"
    And I press "Save"
    Then I should be on "admin/structure/webform/add"
