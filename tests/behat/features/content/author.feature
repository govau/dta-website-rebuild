@api

Feature: Authors
  In order to provide users with  content
  As an author
  I need to be able to create, manage and maintain content.

  Background:
    Given I am logged in as a user with the "Content author" role

    @content @content-author
    Scenario: Create content
    Then print last response
    Then show last response
      When I follow "Content"
      And I follow "Add content"
      Then I should see 12 ".admin-list a" elements

    @content @content-author
    Scenario: Edit content
      Then I should be able to edit a "page"
      And I should be able to edit a "landing_page"
      And I should be able to edit a "media_release"
      And I should be able to edit a "landing_page_level_2"
      And I should be able to edit an "assessment_report"
      And I should be able to edit a "blog_post"
      And I should be able to edit a "news_item"
      And I should be able to edit an "external_link"
      And I should be able to edit a "home_page"
      And I should be able to edit a "roadmap_2_user_experience_"
      And I should be able to edit an "individual_roadmap_ux"
      And I should be able to edit a "dashboard_item"

    @menus @content-author
    Scenario: Edit menus
      When I follow "Structure"
      And I follow "Menus"
      Then I should see 2 "td.menu-label" element
