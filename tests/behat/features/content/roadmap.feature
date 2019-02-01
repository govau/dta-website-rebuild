@api

Feature: Roadmaps
  In order to show users the future of various projects
  As a content editor
  I need to be able to show roadmaps on the site.

  Background:
    Given "quarters_calendar" terms:
    | name |
    | fy_1 |
    And "roadmap_themes" terms:
    | name  |
    | theme |
    And "individual_user_journeys" terms:
    | name    |
    | journey |
    And "agencies" terms:
    | name   |
    | agency |
    And "individual_roadmap_ux" content:
    | title        | field_body    | field_quarter | field_theme | field_individual_user_journey | field_agency | status |
    | roadmap item | delivery goal | fy_1          | theme       | journey                       | agency       | 1      |
    And I am an anonymous user

  @roadmap
  Scenario: Check individual roadmap exists
    When I am on "/our-projects/digital-transformation-roadmaps/roadmap-for-individuals"
    Then I should get a 200 HTTP response

  @roadmap
  Scenario: Check business roadmap exists
    When I am on "/our-projects/digital-transformation-roadmaps/roadmap-for-business-users"
    Then I should get a 200 HTTP response

  @roadmap
  Scenario: Check DTS roadmap exists
    When I am on "/digital-transformation-strategy/roadmap-page"
    Then I should get a 200 HTTP response
