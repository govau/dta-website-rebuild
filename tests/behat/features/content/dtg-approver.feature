@api

Feature: DTG authors
  In order to provide users with digital transformation guide content
  As a digital transformation guide approver
  I need to be able to create, manage and maintain content.

  Background:
    Given I am logged in as a user with the "Digital Transformation Guide approver" role

    @content @dtg-approver
    Scenario: Edit content
      Then I should be able to edit a page
      And I should be able to edit a landing_page
      And I should be able to edit a landing_page_level_2
      And I should be able to edit a govcms_event
      And I should be able to edit an external_link

      @content @dtg-approver
      Scenario: Publish content
        When I am on "node/add/page"
        And I select "Published" from "Save as"
