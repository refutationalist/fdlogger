

var logtable = {

	element: null,
	body:    null,
	logid:   0,
	noteid:  0,
	range:   null,

	start: function(e, data) {
		logtable.element = e;
		logtable.body = e.querySelector("tbody");
		
		logtable.range = document.createRange();
		logtable.range.selectNodeContents(logtable.body);
		logtable.update(data);
	},

	update: function(input) {

		for (x in input.reverse()) {

			if (input[x].kind == "log") {
				logtable.addlog(input[x]);
			} else {
				logtable.addnote(input[x]);
			}

		}

	},

	addlog: function(row) {

		row.id = parseInt(row.id);

		if (logtable.logid < row.id) logtable.logid = row.id;


		let logclass = (row.notes != null) ? "hasfollow" : "";

		let tfreq = logtable.freqconvert(row.freq);
		let ttime = new Date(row.logged + "Z").loggedString();


		let str = 
			`<tr class="${logclass}">`+
			`<td>${ttime}</td><td>${row.csign}</td>`+
			`<td>${row.exch}</td><td>${row.band}</td><td>${tfreq}</td>`+
			`<td>${row.mode}</td><td>${row.handle}</td>`+
			`</tr>`;

		if (row.notes != null)

			str += 
				'<tr class="isfollow"><td colspan="7">'+
				'<label>Note for Above Entry: </label>'+
				row.notes.htmlsafe() +
				'</td></tr>';

		logtable.add(str);

	},


	addnote:  function(row) {
		row.id = parseInt(row.id);
		if (logtable.noteid < row.id) logtable.noteid = row.id;

		let str =
			`<tr class="solonote"><td colspan="7"><div>`+
			`<label>Note from ${row.handle}: </label>`+
			row.notes.htmlsafe() +
			'</div></td></tr>';
	
		logtable.add(str);

	},

	add: function(string) {

		// there might be a more performant way to to do this
		//logtable.body.innerHTML = string + logtable.body.innerHTML;
		let node = logtable.range.createContextualFragment(string);
		logtable.body.prepend(node);
	},


	freqconvert: function(freq) {
		let txt = '';

		if (freq > 1300000000) {
			txt = parseFloat((freq / 1000000000).toFixed(3)) + " GHz";
		} else if (freq > 10000000) {
			txt = parseFloat((freq / 1000000).toFixed(3)) + " MHz";
		} else if (freq / 1000) {
			txt = parseFloat((freq / 1000).toFixed(3)) + " kHz";
		} else {
			txt = freq + " Hz";
		}

		return txt;


	},

	trigger: function() {
		interact([
			{
				cmd: "since",
				arg: [ logtable.logid, logtable.noteid ],
				work: function(r) {
					logtable.update(r.data);
				}
			}
		]);
	}




};

document.addEventListener("DOMContentLoaded", function() {

	/* ugh. */

	let limit = (location.href.split("/").slice(-1) == "full.html") ? 0 : 50;
	
	interact([
		{
			cmd: "get",
			arg: [ limit ],
			work: function(r) {
				logtable.start(document.querySelector("#history table"), r.data);
			}
		}
	]);

	if (limit != 0) setInterval(logtable.trigger, 10000);

});
