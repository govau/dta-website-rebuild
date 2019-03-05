@api

Feature: Mailchimp signup
  In order to receive notifications of new blogs and news items
  As a user
  I need to be able to sign up to Mailchimp through the site.

  Background:
    Given "news_item" content:
    | title     | field_publication_date | field_body  | field_summary | moderation_state | status |
    | Test news | 1/1/2018   | placeholder | placeholder   | published        | 1      |
    And "blog_post" content:
    | title     | field_publication_date | field_body  | field_summary | moderation_state | status |
    | Test blog | 1/1/2018   | placeholder | placeholder   | published        | 1      |
    And "page" content:
    | title                | field_introduction | field_body  | field_summary | moderation_state | status |
    | Subscribe to updates | placeholder        | placeholder | placeholder   | published        | 1      |
    And I am logged in as a user with the "administrator" role
    When I am on "subscribe-updates"
    And I click "Edit" in the "content" region
    And I uncheck "Generate automatic URL alias"
    And I fill in "/news-and-blogs/subscribe-updates" for "URL alias"
    And I press "Save"
    And I am an anonymous user

  @anon @mailchimp @news
  Scenario: Form on news pages
    When I am on "news/test-news"
    Then I should see an "form#mailchimp-signup-subscribe-block-sign-up-for-updates-form" element

  @anon @mailchimp @blogs
  Scenario: Form on blogs pages
    When I am on "blogs/test-blog"
    Then I should see an "form#mailchimp-signup-subscribe-block-sign-up-for-updates-form" element


  @anon @mailchimp
  Scenario: Standalone form
    When I am on "news-and-blogs/subscribe-updates"
    Then the response status code should be 200
    And I should see an "form#mailchimp-signup-subscribe-block-sign-up-for-updates-form" element
