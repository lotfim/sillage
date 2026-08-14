(function () {
	'use strict';

	var cfg = window.sillageTracker;
	if (!cfg || !cfg.sessionToken || !cfg.exitUrl) {
		return;
	}

	var sent = false;

	function sendExit() {
		if (sent) {
			return;
		}
		sent = true;

		var body = JSON.stringify({ session_token: cfg.sessionToken });
		var blob = new Blob([body], { type: 'text/plain' });
		var joiner = cfg.exitUrl.indexOf('?') === -1 ? '?' : '&';
		var url = cfg.exitUrl + joiner + '_wpnonce=' + encodeURIComponent(cfg.restNonce);

		if (navigator.sendBeacon) {
			navigator.sendBeacon(url, blob);
			return;
		}

		try {
			fetch(url, {
				method: 'POST',
				body: blob,
				keepalive: true,
				credentials: 'same-origin',
			});
		} catch (e) {
			// Best-effort only.
		}
	}

	document.addEventListener('visibilitychange', function () {
		if (document.visibilityState === 'hidden') {
			sendExit();
		}
	});

	window.addEventListener('pagehide', sendExit);
})();
