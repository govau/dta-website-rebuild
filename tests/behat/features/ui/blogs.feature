@api

Feature: Blogs and news items
  In order to learn more about the DTA's products, services and events
  As a user
  I need to be able to view the blogs and news items.

  Background:
    Given "blog_post" content:
    | title       | field_body       | field_publication_date | status | moderation_state | field_summary  |
    | test blog 1 | test blog 1 body | 1/1/2017   | 1      | published        | test summary 1 |
    | test blog 2 | test blog 2 body | 1/1/2017   | 1      | published        | test summary 2 |
    | test blog 3 | test blog 3 body | 1/1/2020   | 1      | published        | test summary 3 |
    | test blog 4 | test blog 4 body | 1/1/2020   | 1      | published        | test summary 4 |
    | test blog 5 | test blog 5 body | 1/1/2020   | 1      | published        | test summary 5 |
    And I run cron

    @taxonomy
    Scenario: Viewing tag pages/taxonomy terms
      Given I am viewing a "Tags" term with the name "test"
      Then I should see the text "test" in the "content" region

    @blogs
    Scenario: Viewing single blogs and news items
      When I visit "blogs/test-blog-1"
      Then the response status code should be 200
      And I should see the heading "test blog 1"

    @blogs
    Scenario: Viewing all blogs and news items
      When I visit "news-blogs/all"
      Then I should see the text "News and blogs" in the "breadcrumbs" region
      Then print last response
      Then show last response     
      And I should see 3 "li.col-xs-12" elements
      And I should see the text "We only display blog posts and news items from the last year. For older items, please visit our archive page."
      And I should see the link "archive page"
      And I should see the heading "Filter by type"
      And I should see the heading "Filter by tags"
      And I follow "archive page"
      Then I should see the text "News and blogs archive" in the "breadcrumbs" region

    @blogs @archive
    Scenario: Viewing archived blogs and news items
      When I visit "news-blogs-archive"
      Then I should see the text "News and blogs archive" in the "breadcrumbs" region
      And I should see 2 "li.col-xs-12" elements
      And I should see the text "This is our archive of older items. For more recent items, please visit our news and blogs page."
      And I should see the link "news and blogs page"
      And I should see the heading "Filter by type"
      And I should see the heading "Filter by tags"
