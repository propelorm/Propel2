Feature: Table
  In order to map my business objects to the database
  As a developer
  I need to be able to describe a schema

  Scenario: Successfully created a table
    Given a platform is "MysqlPlatform"
    And I have XML schema:
      """
      <database name="behat_table">
        <table name="a_table"></table>
      </database>
      """
    When I generate SQL
    Then it should contain:
      """
      CREATE TABLE `a_table`
      """
