@api

Feature: Events
  In order to learn more about the DTA's events
  As a user
  I need to be able to view and search for events.

  Background:
    Given "events" content:
    | title        | field_date_range:value | field_date_range:end_value | status | moderation_state | field_summary  |
    | test event 1 | now -1 years           | now -1 years +1 day        | 1      | published        | test summary 1 |
    | test event 2 | now +1 years           | now +1 years +1 day        | 1      | published        | test summary 2 |
    And I run cron

    @events
    Scenario: Viewing single event
      When I visit "event/test-event-1"
      Then the response status code should be 200
      And I should see the heading "test event 1"

    @events
    Scenario: Viewing all events
      When I visit "events"
      Then I should see the text "Events" in the "breadcrumbs" region
      Then print last response
      Then show last response
      And I should see 2 "li.col-xs-12.col-sm-6" element
      And I should see the text "This page shows our upcoming events. If you would like to view our past events, please head to our events archive."
      And I should see the link "please head to our events archive"
      And I should see the heading "Filter by category"
      And I follow "please head to our events archive"
      Then I should see the text "Past events" in the "breadcrumbs" region

    @events @archive
    Scenario: Viewing archived events
      When I visit "events/archive"
      Then I should see the text "Past events" in the "breadcrumbs" region
      And I should see 1 "li.col-xs-12.col-sm-6" elements
      And I should see the text "This page lists past events. We provide them for your information only. If you would like to see our upcoming events, please head to our Events page."
      And I should see the link "please head to our Events page"
      And I should see the heading "Filter by category"

    @events @search
    Scenario: Searching for events
      When I go to "search?keys=test"
      Then I should see the text "test event 1"
      And I should see the text "test event 2"
      And I should see 2 "h3 a" elements
