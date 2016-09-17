# Elgg-mrclay_poll_svc
This provides an API to allow clients to "listen" for server-side events on Elgg entities. While this could be done real-time with a libevent-based setup like Node, this emulates the listening in simple PHP environments by polling JSON file(s) on the server.

## Client-side API

Using the service to listen for any server-side "comments" events:

```js
define(function (require) {
    var svc = require('mrclay_poll_svc');

    // This lets the HTML page determine which entities to listen for. If the page hasn't used
    // request_connection(), no connections will be monitored.
    svc.onChannelUpdate("comments", function (update) {
        // an update occurred!
        console.log(update); // {guid:123,channel:"comments",action:"ping",time:<Date>,messages:[]}
    });
});
```

Listening for events on a particular GUID:

```js
define(function (require) {
    var svc = require('mrclay_poll_svc'),
        guid = /* sniff from HTML */;
        
    svc.getConnection(guid).done(function (conn) {
        // listen for comments events
        conn.onUpdate('comments', function (update) {
            // an update occurred! update the comments list
            console.log(update); // {channel:"comments",action:"ping",time:<Date>,messages:[]}
        });
        
        // listen to all events
        conn.onUpdate(function (update) {
            // some update occurred!
        });
    });
});
```

Low-level access to all Updates:

```js
define(function (require) {
    var svc = require('mrclay_poll_svc');

    // Note this doesn't start() any connections. You must use svc.getConnection() or
    // svc.onChannelUpdate() to start polling particular entities/channels.
    elgg.register_hook_handler('mrclay_poll_svc', 'all', function (h, t, p, v) {
        // all events for a single connection
        var guid = p.guid;
        $.each(p.updates, function (event_name, update) {
            //
        });
    });
});
```

## Server-side API

Set up the connection and client for listening for events on an entity:

```php
if (elgg_is_logged_in()) {
	// make sure the blog has a comments channel to listen to
	MrClay\Elgg\PollService\add_channels($blog, ['comments']);
	
	// prepare the client for listening to this connection
	MrClay\Elgg\PollService\request_connection($blog);
}
```

Send events to listening clients:

```php
// e.g. upon creating a new comment
MrClay\Elgg\PollService\ping_channel($entity, 'comments');
```

## Design

A *connection* is a transport mechanism for events on one entity. It's stored as a single JSON file, containing a named set of *channels*.

A *channel* stores the last time the channel was updated (0 for not yet), and an array of messages (of any JSON type).

The client listens to a connection by polling its JSON file and watching for changes. Changes are put into a structured object and passed into an elgg event. The Connection's `onUpdate` method takes the hassle out of listening to the raw Elgg events.

Listening doesn't start until the client adds listeners.
