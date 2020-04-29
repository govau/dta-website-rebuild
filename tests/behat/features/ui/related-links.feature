@api

Feature: Related links
  In order to learn more about the DTA's products, services and events
  As a user
  I need to be able to view related links on different content types

  Background:
    Given "blog_post" content:
    | title             | field_body             | field_publication_date | status | moderation_state | field_summary        |
    | Related link blog | Related link blog body | 1/1/2017               | 1      | published        | Related link blog summary |

    Given "news_item" content:
    | title             | field_body             | field_publication_date | status | moderation_state | field_summary        |
    | Related link news | Related link news body | 1/1/2017               | 1      | published        | Related link news summary |


    And "page" content:
    | title       | field_body       | status | moderation_state | field_summary       | field_related_content                | field_related_content_heading |
    | Source page | Source page body | 1      | published        | Source page summary | Related link blog, Related link news | Related links                 |

    And I run cron

    @anonymous @related-links
    Scenario: Related link cards for blogs
      When I am an anonymous user
      And I visit "source-page"
      Then the response status code should be 200
      And I should see the heading "Source page"
      And I should see the heading "Related content"
      And I should see the heading "Related link blog"
      And I should see the heading "Related link news"
      And I should see the text "Related link blog summary"
      And I should see the text "Related link news summary"
