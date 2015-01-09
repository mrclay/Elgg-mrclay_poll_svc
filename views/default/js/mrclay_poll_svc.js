/**
 * This module returns an object that gives access to a collection of Connection objects, each representing
 * an Elgg entity. Each connection has a named set of channels that may be updated server-side.
 *
 * To start listening to a connection (polling the JSON file), client code must require this module and use
 * getConnection(GUID). You can also call the @api methods on the connection to make it more responsive, and
 * register a handler to listen for updates.
 */
define('mrclay_poll_svc', function (require) {
    var elgg = require('elgg'),
        $ = require('jquery');

    if (!window.mrclay_poll_svc_data) {
        return;
    }

    function Connection(guid, data) {
        // delay for the next request
        var self = this,
            url = data.url,
            last_data = data.init,
            updates = {},
            commands = [],

            delay = 10000,
            // when we receive a change, we decrease delay to minDelay and slowly increase it to targetDelay
            targetDelay = 60000,
            minDelay = 3000,

            started = false,
            waiting = false,
            stopped = false;

        function schedulePool() {
            setTimeout(poll, delay);

            var delta = (targetDelay - delay);
            delay += delta * .1;
        }

        function schedulePool() {
            setTimeout(poll, delay);

            var delta = (targetDelay - delay);
            delay += delta * .1;
        }

        function poll() {
            if (waiting) {
                setTimeout(poll, 1000);
                return;
            }

            waiting = true;
            $.ajax({
                url: elgg.normalize_url(url),
                dataType: 'json',
                ifModified: true
            }).done(function (data) {
                waiting = false;
                self.receiveData(data);
            });

            if (!stopped) {
                schedulePool();
            }
        }

        /**
         * Set the maximum expected delay (in ms) between polling requests
         *
         * @api
         * @param {Number} suggestedDelay (ms)
         */
        this.suggestDelay = function (suggestedDelay) {
            suggestedDelay = Math.max(minDelay, suggestedDelay);
            targetDelay = Math.min(targetDelay, suggestedDelay);
        };

        /**
         * Speed up polling temporarily
         *
         * @api
         */
        this.throttleUp = function () {
            delay = minDelay;
        };

        /**
         * Register a handler to receive new updates on a channel. Leave out the "name" parameter to
         * receive each update on the connection.
         *
         * Internal note: updates are delivered via the elgg plugin hook [mrclay_poll_svc, update:GUID]
         * where GUID identifies the connection.
         *
         * @api
         * @param {String} name (optional)
         * @param {Function} handler This handler will receive a single Update object
         */
        this.onUpdate = function (name, handler) {
            if ($.isFunction(name)) {
                handler = name;
                name = '';
            }

            elgg.register_hook_handler('mrclay_poll_svc', 'update:' + guid, function (h, t, p, v) {
                $.each(p.updates, function (key, updates) {
                    if (!name) {
                        handler(updates);
                        return;
                    }

                    if (key === name) {
                        handler(updates);
                    }
                });
            });
        };

        /**
         * Start polling
         */
        this.start = function () {
            if (!started) {
                started = true;
                schedulePool();
            }
        };

        /**
         * Update data
         *
         * @param {Object} data object from the JSON file
         */
        this.receiveData = function (data) {
            if (!data) {
                return;
            }

            updates = {};
            $.each(data, function (key, val) {
                if (last_data[key]) {
                    if (last_data[key].t !== val.t) {
                        updates[key] = new Update('ping', new Date(val.t * 1000));
                    }
                } else {
                    updates[key] = new Update('create', new Date(val.t * 1000));
                }
            });

            $.each(last_data, function (key, val) {
                if (!data[key]) {
                    updates[key] = new Update('delete', null);
                }
            });

            var params = {
                guid: guid,
                updates: updates
            };

            if (!$.isEmptyObject(updates)) {
                commands = elgg.trigger_hook('mrclay_poll_svc', 'update:' + guid, params, []);
                // TODO do something with commands?
            }

            last_data = data;
        };
    }

    function Update(action, time) {
        this.action = action;
        this.time = time;
    }

    /**
     * @type {Connection[]}
     */
    var connections = {};

    // you only have connections that were given by the page
    // TODO allow requesting a new connection
    $.each(mrclay_poll_svc_data, function (guid, init) {
        if (typeof init === 'object') {
            connections[guid] = new Connection(guid, init);
        }
    });

    var exports = {
        /**
         * Get a Connection and start listening to it
         *
         * @api
         * @param {Number} guid
         * @returns {Deferred} use .done() to receive the connection
         */
        getConnection: function (guid) {
            var def = $.Deferred();

            if (connections[guid]) {
                connections[guid].start();
                def.resolve(connections[guid]);
            } else {
                $.getJSON(elgg.normalize_url('mrclay_poll_svc/fetchConnection/' + guid), function (data) {
                    if (typeof data === 'object') {
                        connections[guid] = new Connection(guid, data);
                        connections[guid].start();
                        def.resolve(connections[guid]);
                    } else {
                        // data is an error message
                        def.reject(data);
                    }
                });
            }

            return def;
        },

        /**
         * Get the names of all available connections
         *
         * @api
         * @returns {String[]}
         */
        getNames: function () {
            return $.map(connections, function(connection, name) {
                return name;
            });
        }
    };

    // testing
    //window.mrclay_poll_svc = exports;

    return exports;
});
