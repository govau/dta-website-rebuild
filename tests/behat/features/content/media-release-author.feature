@api

Feature: Media release authors
  In order to provide users with media release content
  As an media release author
  I need to be able to create, manage and maintain media releases.

  Background:
    Given I am logged in as a user with the "Media release author" role

    @content @media-release-author
    Scenario: Create content
      When I follow "Content"
      And I follow "Add content"
      Then I should see the heading "Create Media release"

    @content @media-release-author
    Scenario: Edit content
      Then I should be able to edit a "media_release"

    @menus @media-release-author
    Scenario: Edit menus
      When I follow "Structure"
      Then I should see the text "You do not have any administrative items"
