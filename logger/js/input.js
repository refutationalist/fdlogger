document.addEventListener("DOMContentLoaded", function() {
	interact([
		{
			cmd: "modes",
			work: logit.start
		},
		{
			cmd: "classes",
			work: function(r) { logit.clsss = r.data; }
		},
		{
			cmd: "radios",
			work: logit.radios
		},
		{
			cmd: "zones",
			work: function(r) { logit.zones = r.data; }
		}
	]);
});


var logit = {

	e:        {},
	zones:    null,
	rstr:     null,
	clsss:    null,
	radiochk: null,
	booklook: null,
	dupelook: null,
	data:     new logrecord(),

	start: function(r) {

		let find = [
			'entry', 'freq', 'mode', 'handle', 'radio', // input elements

			'call', 'exchange', // main parse display

			'cname', 'cloc', 'tx', 'clss', 'zone', 'notes', // detail elements

			'status', 'parseinner' // status elements
		];

		for (x in find) {
			logit.e[ find[x] ] = document.getElementById( find[x] );

			if (logit.e[ find[x] ] == null) 
				shit('UI', `couldn't find proper ${find[x]} element`);
		}


		logit.e.radio.addEventListener("change", logit.radio_select);
		logit.e.entry.addEventListener("input", logit.process);
		logit.e.entry.value = null;

		logit.e.freq.addEventListener("blur", logit.freqfmt);
		logit.freqfmt();



		for (x in r.data) 
			logit.e.mode.innerHTML += `<option>${r.data[x]}</option>\n`;

		logit.e.mode.value = logit.e.mode.options[0];

		logit.radio_clear();

	},


	freqfmt: function(evt) {

		let element = logit.e.freq;

		if (element.value) {
			let numerical = parseInt(element.value.replace(/\D/g, ''));
			element.value = numerical.toLocaleString("de-DE"); // dots not commas
			element.numerical = numerical;
		} else {
			element.value = element.numerical = 0;
		}

		logit.process();


	},


	radios: function(r) {

		let rstr = r.data.join();

		if (rstr != logit.rstr) {
			logit.e.radio.innerHTML = "<option>MANUAL</option>";

			for (x in r.data) 
				logit.e.radio.innerHTML += `<option>${r.data[x]}</option>`;

			logit.rstr = rstr;
		}

	},

	radio_select: function(evt) {

		let radio = evt.target.value;
		clearInterval(logit.radiochk);
		

		if (radio == 'MANUAL') {
			logit.radio_clear();
		} else {

			let radio_update = function() {
				interact([
					{
						cmd: 'radio',
						arg: [ radio ],
						work: logit.radio_data
					}
				]);
			}

			radio_update();
			logit.radiochk = setInterval(radio_update, 3000);
		}


	},

	radio_clear: function() {
		logit.e.radio.value = 'MANUAL';
		logit.e.freq.disabled = false;
		logit.e.freq.classList.remove('locked');
		logit.e.freq.value = 0;
		
		logit.e.mode.disabled = false;
		logit.e.mode.classList.remove('locked');
		logit.e.mode.value = 'CW';
	},

	radio_data: function(r) {

		if (r.data.noradio == true) {
			logit.radio_clear();
			clearInterval(logit.radiochk);
			shit('UI', "Your radio configuration has disappeared.");
		} else {
			logit.e.freq.classList.add('locked');
			logit.e.freq.value = r.data.freq;
			logit.e.freq.dispatchEvent(new Event('blur'));
			logit.e.freq.disabled = true;

			if (r.data.mode == "UNK") {
				logit.e.mode.classList.remove('locked');
				logit.e.mode.disabled = false;
			} else {
				logit.e.mode.value = r.data.mode;
				logit.e.mode.classList.add('locked');
				logit.e.mode.disabled = true;
			}

		}


	},

	

	entry_clear: function() {
		console.log("clearing entry data");
		logit.data = new logrecord();

		let pop = [
			'call', 'exchange',
			'cname', 'cloc',
			'tx', 'clss', 'status',
			'zone', 'notes'
		];
		for (x in pop) logit.e[ pop[x] ].innerHTML = null;
		logit.e.entry.value = null;
		logit.e.status.classList.remove('bad','good');
		

	},


	// This is where the nightmarish magic happens.
	process: function(evt) {

		if (logit.e.entry.value == "") {
			logit.entry_clear();
			return;
		}
		let parsed = parse.go(logit.e.entry.value);
		parsed.freq = logit.e.freq.numerical;
		parsed.mode = logit.e.mode.value;

		let old    = logit.data;

		if (parsed.str() == old.str()) return;

		// notes
		logit.e.notes.innerHTML = (parsed.notes) ? "Yes" : "No";

		// call
		if (parsed.call != old.call) {
			logit.callbook_clear();
			logit.e.call.innerHTML = parsed.call;

			// I thought about not doing a lookup for DX but it's possible
			// for people with US/CA calls to not be in the US/CA.
			if (parsed.call != null)
				logit.callbook(parsed.call);

		}

	
		// tx
		if (parsed.tx != old.tx) {
			logit.e.tx.innerHTML = parsed.tx;
		}

		let update_exch = false;

		// class
		if (parsed.clss != old.clss) {
			update_exch = true;
			if (logit.clsss[parsed.clss]) {
				logit.e.clss.innerHTML = `${parsed.clss} &mdash; ${logit.clsss[parsed.clss]}`;
			} else {
				logit.e.clss.innerHTML = null;
				parsed.clss = null;
			}
		}

		// zone
		if (parsed.zone != old.zone) {
			update_exch = true;
			if (logit.zones[ parsed.zone ]) {
				logit.e.zone.innerHTML = `${parsed.zone} &mdash; ${logit.zones[parsed.zone]}`;
			} else {
				logit.e.zone.innerHTML = null;
				parsed.zone = null;
			}
		}

		// output exch
		if (update_exch) {
			let exch = null;

			if (parsed.clss != null && parsed.zone != null) {
				exch = `${parsed.tx}${parsed.clss}-${parsed.zone}`;
			} else if (parsed.clss != null) {
				exch = `${parsed.tx}${parsed.clss}-???`;
			} else if (parsed.zone != null) {
				exch = `??-${parsed.zone}`;
			}
			logit.e.exchange.innerHTML = exch;

		}


		// FIXME pull in freq, mode, do dupe check
		

		console.log("islog", parsed.islog(), "isnote", parsed.isnote());
		logit.data = parsed;

	},

	callbook: function(call) {

		if (logit.lookbook) {
			clearTimeout(logit.lookbook);
			logit.lookbook = null;
		}

		if (call != null) {
			logit.lookbook = setTimeout(function() {
				interact([
					{
						cmd: "callbook",
						arg: [ call ],
						work: logit.callbook_read
					}
				]);
			}, 500);
		} 
	},

	callbook_read: function(r) {
		let name, log;

		if (r.data.name) {
			name = r.data.name;
			loc  = r.data.city+', '+r.data.state;
		} else {
			name = 'NO DATA';
			loc  = 'NO DATA';
		}

		logit.e.cname.innerHTML = name;
		logit.e.cloc.innerHTML = loc;
	},

	callbook_clear: function() {
		logit.e.cname.innerHTML = logit.e.cloc.innerHTML = null;
	},


	dupe: function(call) {


		if (logit.dupelook) {
			clearTimeout(logit.dupelook);
			logit.dupelook = null;
		}

		if (
			logit.data.call != null &&
			logit.data.freq != null && logit.data.freq > 0 &&
			logit.e.mode.value != null
		) {

			logit.dupelook = setTimeout(function() {

				interact([
					{
						cmd: 'dupe',
						arg: [ 
							logit.data.call,
							logit.data.freq,
							logit.e.mode.value
						],
						work: logit.dupe_read
					}
				]);

			}, 500);
		}
	},

	dupe_read: function(r) {
		console.log(r.data);

		if (r.data != null) {
			console.log("DUPE");

			logit.e.stats.innerHTML =
				"DUPE on " +
				new Date(r.data.logged + 'Z').loggedString();

			logit.e.box.classList.add('bad');
			logit.e.box.classList.remove('good');
			logit.data.nodupe = false;


		} else {
			logit.e.stats.innerHTML = "No Dupe";
			logit.data.nodupe = true;
			logit.e.box.classList.add('good');
			logit.e.box.classList.remove('bad');

			
		}
	}






};
