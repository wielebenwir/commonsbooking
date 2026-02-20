# How do I show the booking comment on the page and in the email?

In the settings you can enable booking comments. In the email templates you then need to insert the following code: <div v-pre>`{{booking:returnComment}}`</div>
