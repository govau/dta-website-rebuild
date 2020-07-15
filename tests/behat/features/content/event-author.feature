@api

Feature: Event authors
  In order to provide users with event content
  As an event author
  I need to be able to create, manage and maintain events.

  Background:
    Given I am logged in as a user with the "Event editor" role

    @content @event-author
    Scenario: Create content
      When I follow "Content"
      And I follow "Add content"
      Then I should see the link "Events"
      And I should see the link "Presentation"
      And I should see the link "Speaker bio"

    @content @event-author
    Scenario: Edit content
      Then I should be able to edit an "events"
      And I should be able to edit a "presentation"
      And I should be able to edit a "speaker_bio"

    @menus @event-author
    Scenario: Edit menus
      When I follow "Structure"
      Then I should see the link "Taxonomy"
