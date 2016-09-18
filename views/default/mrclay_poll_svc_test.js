define(function (require) {
	var svc = require('mrclay_poll_svc');
	var elgg = require('elgg');
	var $ = require('jquery');

	// TODO: since creating a like doesn't refresh the page, a client sending a like will also
	// receive the update. The client should capture the annotation ID and make sure the latest
	// likes message doesn't match that.

	svc.onChannelUpdate('comments', function (update) {
		elgg.system_message('comments added! See console for details.');
		console.log('comments:', update);
	});

	svc.onChannelUpdate('likes', function (update) {
		elgg.system_message('likes added! See console for details.');
		console.log('likes:', update);
	});
});
