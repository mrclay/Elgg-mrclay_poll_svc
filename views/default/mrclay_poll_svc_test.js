define(function (require) {
	var svc = require('mrclay_poll_svc');
	var elgg = require('elgg');
	var $ = require('jquery');

	svc.onChannelUpdate('comments', function (update) {
		elgg.system_message('comments added! See console for details.');
		console.log('comments:', update);
	});

	svc.onChannelUpdate('likes', function (update) {
		elgg.system_message('likes added! See console for details.');
		console.log('likes:', update);
	});
});
