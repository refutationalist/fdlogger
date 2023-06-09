/* protoypes */

Date.prototype.addMS = function (ms) {
  let date = new Date(this.valueOf());
  date.setMilliseconds(date.getMilliseconds() + ms);
  return date;
};

Date.prototype.loggedString = function() {
	let output = 
		(this.getMonth()+1) + '-' + this.getDate() + ' ' +
		('0' + this.getUTCHours()).substr(-2) + ':' +
		('0' + this.getUTCMinutes()).substr(-2) + ':' +
		('0' + this.getUTCSeconds()).substr(-2);
	return output;
}

String.prototype.htmlsafe = function() {
	let out = this.replace(
		/[&<>'"{}]/g,
		tag =>
			({
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				"'": '&#39;',
				'"': '&quot;',
				'{': '<pre>',
				'}': '</pre>'
			}[tag] || tag)
	);

	return out;
	 
};

/* error handling */
function shit(cmd = "none", input = "") {

	if (!document.body.classList.contains('error'))
		document.body.classList.add('error');


	document.getElementById("errortext").innerHTML += ` (${cmd}) ${input}`;
	console.error(`LOGGER ERROR [${cmd}]: ${input}`);
}

window.onerror = function(message, source, lineno, colno, error) {
	shit('JS', `${message} [${source} ${lineno}:${colno}] (${error})`);
}


document.addEventListener("DOMContentLoaded", function() {
	document.querySelector("#error div.clear").addEventListener(
		"click", 
		function(evt) {
			document.getElementById("errortext").innerHTML = "";
			document.body.classList.remove('error');
		});

});

/* generalized call to API */
async function interact(incoming = {}, apimode = "user") {

	let calls    = { };
	let process = { };

	for (x in incoming) {

		if (!incoming[x].cmd) {
			shit("UI", "Malformed command");
			continue;
		}

		if (!incoming[x].work) {
			shit("UI", `[${incoming[x].cmd}] has no work method`);
			continue;
		}

		calls[x] = { cmd: incoming[x].cmd };
		if (incoming[x].arg) calls[x].arg = incoming[x].arg;

		process[x] = incoming[x].work;


	}

	console.log(JSON.stringify(calls));

	let query = await fetch("api?a="+apimode, {
		method: "POST",
		body: JSON.stringify(calls)
	});

	let results = JSON.parse(await query.text());

	for (i in results) {
		r = results[i];

		if (r.result == false) {
			shit(r.cmd, r.data);
			continue;
		}

		process[i](r);
		
	}

	



}
