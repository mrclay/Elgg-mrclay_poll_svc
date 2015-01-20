# Elgg-mrclay_poll_svc
This provides an API to allow clients to "listen" for server-side events on Elgg entities. While this could be done real-time with a libevent-based setup like Node, this emulates the listening in simple PHP environments by polling JSON file(s) on the server.

## Client-side API

Using the service to listen for any server-side "comments" events:

```js
define(function (require) {
    var svc = require('mrclay_poll_svc');

    svc.onChannelUpdate("comments", function (update) {
        // an update occurred!
        console.log(update); // {guid:123,channel:"comments",action:"ping",time:<Date>}
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
            console.log(update); // {channel:"comments",action:"ping",time:<Date>}
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

    // Note this doesn't start() any connections. You must use getConnection to start polling
    // particular entities
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

Set up a client for listening for events on an entity (this pre-loads the channel data onto the client):

```php
// e.g. on a blog page
MrClay\Elgg\PollService\request_connection($blog)
```

Send events to listening clients:

```php
// e.g. during a save comment action
MrClay\Elgg\PollService\ping_channel($entity, 'comments');
```

## Design

A *connection* is a transport mechanism for events on one entity. It's stored as a single JSON file, containing a named set of *channels*, which store only (for now) the last time the channel was pinged.

The client listens to a connection by polling its JSON file and watching for changes. Changes are put into a structured object and passed into an elgg event. The Connection's `onUpdate` method takes the hassle out of listening to the raw Elgg events.
