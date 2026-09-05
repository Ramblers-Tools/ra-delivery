When saving data from Step 7, the value for "domain" must be used to create a "sub-account" on
smtp2go.com, then to create a new API key for this sub-account. The returned API key value is to be stored in a record in table #__ra_api_sites.

The existing helper com_ra_delivery / site / helpers / SmtpHelper.php gives demonstrates how the table ra_api_sites stores the API keys, and contains examples of other calls to smtp2go.com.

To add a sub-account, see https://developers.smtp2go.com/reference/add-subaccount
To ass a new API key, see https://developers.smtp2go.com/reference/add-api-key

Review the existing codebase and the remote documentation, construct a document with a plan for implementation, and list any unresolved design decisions
