document.addEventListener("DOMContentLoaded", function() {
	logit.start();
});


var logit = {

	e:         {},    // elements used by this class
	zones:     null,  // zones, comes in via API call
	clsss:     null,  // classes, as above
	radiochk:  null,  // the interval for radio data checking
	booklook:  null,  // the timeout for callbook lookups
	dupelook:  null,  // the timeout for dupe checks

	radio:     "MANUAL",        // the radio currently selected
	rstr:      null,            // a simple compare string for radio dropdown
	data:      new logrecord(), // the parsed record

	delay: {   // timeout and lookup delays
		lookup: 400,
		radio:  3000
	},

	start: function(r) {

		/* init 1: find all the things */
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

		
		/* init 2: set initial state */
		logit.e.mode.value = logit.e.mode.options[0];
		logit.radio_clear();
		logit.entry_clear();
		logit.e.handle.value = "";

		/* init 3: get the data from the API */
		interact([
			{
				cmd: "modes",
				work: function(r) {
					for (x in r.data) 
						logit.e.mode.innerHTML += `<option>${r.data[x]}</option>\n`;
				}
			},
			{
				cmd: "classes",
				work: function(r) { logit.clsss = r.data; }
			},
			{
				cmd: "zones",
				work: function(r) { logit.zones = r.data; console.log("Zones", r.data);}
			}
		]);

		/* init 4: set all the UI interactions */
		logit.e.radio.addEventListener("change", logit.radio_select);

		logit.e.freq.addEventListener("blur", logit.freqfmt);
		logit.freqfmt();

		logit.e.entry.addEventListener("input", logit.process);
		logit.e.entry.onkeydown = function(evt) {
			if (evt.keyCode == 13) 
				logit.submit();
		}
		

		/* init 5: start radio update interval */
		logit.radio_get();
		logit.radiochk = setInterval(logit.radio_get, logit.delay.radio);

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


	radio_get: function() {
		let q = [{
			cmd: "radios",
			work: logit.radios
		}];

		if (logit.radio != 'MANUAL') {
			q.push({
				cmd: "radio",
				arg: [ logit.radio ],
				work: logit.radio_data
			});
		}

		interact(q);
			
	},



	radios: function(r) {

		if (r.data.join() != logit.rstr) {

			logit.e.radio.innerHTML = "<option>MANUAL</option>";

			for (x in r.data)
				logit.e.radio.innerHTML += `<option>${r.data[x]}</option>`;



			logit.e.radio.value = logit.radio;

			logit.rstr = r.data.join();

		}

	},

	radio_select: function(evt) {
		logit.radio = evt.target.value;
		if (logit.radio == 'MANUAL') {
			logit.radio_clear();
		}
		logit.radio_get();
	},

	radio_clear: function() {
		logit.e.radio.value = logit.radio = 'MANUAL';
		logit.e.freq.disabled = false;
		logit.e.freq.classList.remove('locked');
		logit.e.freq.value = 0;
		
		logit.e.mode.disabled = false;
		logit.e.mode.classList.remove('locked');
		logit.e.mode.value = 'CW';
	},

	radio_data: function(r) {

		if (r.data.noradio == true) {
			// our radio went away
			logit.radio_clear();
			logit.process();
			shit('UI', "Your radio configuration has disappeared.");
		} else {

			// disable the freq input
			logit.e.freq.classList.add('locked');
			logit.e.freq.disabled = true;

			// update value and reformat
			logit.e.freq.value = r.data.freq;
			logit.freqfmt();

			// reprocess if frequency change
			if (r.data.freq != logit.data.freq) logit.process();

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
			'tx', 'clss',
			'zone', 'notes'
		];
		for (x in pop) logit.e[ pop[x] ].innerHTML = null;
		logit.e.entry.value = null;
		logit.status_clear();
		

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

		//console.log("islog", parsed.islog(), "isnote", parsed.isnote());

		logit.data = parsed;
		if (logit.data.dupeready()) logit.dupe();

	},


	submit: function() {

		if (logit.e.handle.value == "" || logit.e.handle.value == null) {
			shit("UI", "Please enter a name into the log.");
			return;
		}


		if (logit.data.islog(true)) {
			//submit log
			interact([
				{
					cmd: "add",
					arg: [
						logit.data.call,
						logit.data.tx,
						logit.data.clss,
						logit.data.zone,
						logit.data.freq,
						logit.data.mode,
						logit.e.handle.value,
						logit.data.notes
					],
					work: function(r) {
						console.log("log submitted", r);
						logit.entry_clear();
						logtable.trigger();
					}
				}
			]);
		
		} else if (logit.data.isnote()) {

			interact([
				{
					cmd: "note",
					arg: [
						logit.data.notes,
						logit.e.handle.value
					],
					work: function(r) {
						console.log("note submitted", r);
						logit.entry_clear();
						logtable.trigger();
					}
				}
			]);

		}


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
			}, logit.delay.lookup);
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

		if (logit.data.dupeready()) {

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

			}, logit.delay.lookup);
		} else {
			logit.e.status.innerHTML = "Incomplete";
		}
	},

	dupe_read: function(r) {

		if (r.data != null) {
			console.log("DUPE");


			logit.data.nodupe = false;
			logit.status_bad(
				"DUPE on " +
				new Date(r.data.logged + 'Z').loggedString()
			);


		} else {
			logit.data.nodupe = true;

			if (logit.data.islog(true)) {
				logit.status_good("Complete and Clear!");
			} else {
				logit.status_bad("Incomplete!");
			}
		}
	},


	status_good: function(string) {
		logit.e.parseinner.classList.remove('bad');
		logit.e.parseinner.classList.add('good');
		logit.e.status.innerHTML = string;
	},

	status_bad: function(string) {
		logit.e.parseinner.classList.remove('good');
		logit.e.parseinner.classList.add('bad');
		logit.e.status.innerHTML = string;
	},

	status_clear: function() {
		logit.e.parseinner.classList.remove('good');
		logit.e.parseinner.classList.remove('bad');
		logit.e.status.innerHTML = null;
	}






};
