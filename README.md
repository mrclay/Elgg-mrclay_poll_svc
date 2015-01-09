# Elgg-mrclay_poll_svc
Polling Service API for Elgg

A JSON file holds a Connection.

A Connection holds a set of Channels.

From the server we can update a channel.

A client listens to a connection by polling a JSON file. The client may need to listen
to multiple connections on a page.

E.g. Each Elgg entity might have a connection, with the comments stream as the channel "comments"
