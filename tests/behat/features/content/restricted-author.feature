@api

Feature: Restricted authors
  In order to provide users with basic content
  As an author
  I need to be able to create, manage and maintain content in a limited way.

  Background:
    Given I am logged in as a user with the "Restricted author" role

    @content @restricted-author
    Scenario: Create content
      When I follow "Content"
      And I follow "Add content"
      Then I should see 4 ".admin-list a" elements

    @content @restricted-author
    Scenario: Edit content
      Then I should be able to edit a "page"
      And I should be able to edit a "landing_page_level_2"
      And I should be able to edit an "external_link"
      And I should be able to edit a "govcms_event"

    @menus @restricted-author
    Scenario: Edit menus
      When I follow "Structure"
      And I follow "Menus"
      Then I should see 1 "td.menu-label" element
