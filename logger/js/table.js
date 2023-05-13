

var logtable = {

	element: null,
	body:    null,
	logid:   0,
	noteid:  0,

	start: function(e, data) {
		logtable.element = e;
		logtable.body = e.querySelector("tbody");
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
				`<tr class="isfollow"><td colspan="7">`+
				`<label>Note for Above Entry: </label>`+
				`${row.notes}</td></tr>`;

		logtable.add(str);

	},


	addnote:  function(row) {
		if (logtable.noteid < row.id) logtable.noteid = row.id;
		
		let str =
			`<tr class="solonote"><td colspan="7"><div>`+
			`<label>Note from ${row.handle}: </label>`+
			`${row.notes}</div></td></tr>`;
	
		logtable.add(str);

	},

	add: function(string) {

		// there might be a more performant way to to do this
		logtable.body.innerHTML = string + logtable.body.innerHTML;
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





	}




};

document.addEventListener("DOMContentLoaded", function() { 
	
	interact([
		{
			cmd: "get",
			arg: [ 50 ],
			work: function(r) {
				logtable.start(document.querySelector("#history table"), r.data);
			}
		}
	]);

	setInterval(function() {

		interact([
			{
				cmd: "since",
				arg: [ logtable.logid, logtable.noteid ],
				work: function(r) {
					logtable.update(r.data);
				}
			}
		]);

	}, 10000);

});
