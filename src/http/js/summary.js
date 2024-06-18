/* FIXME This whole thing is a hack. */

document.addEventListener("DOMContentLoaded", function(evt) {

	let output = document.getElementById("summary");


	interact([
		{
			cmd: "count",
			work: function(r) {
				document.getElementById("totals").innerHTML = r.data;
			}
		},
		{
			cmd: "bymode",
			work: function(r) {

				let str =
					"<div><h2>by Mode</h2>"+
					"<table><thead><tr><th>Count</th><th>Mode</th></thead><tbody>";
				for (x in r.data)
					str += `<tr><td>${r.data[x].qso}</td><td>${r.data[x].mode}</td></tr>`;
				str += "</tbody></table></div>";

				output.innerHTML += str;



			}
		},
		{
			cmd: "bycabmode",
			work: function(r) {
				let count = 0;
				
				let str =
					"<div><h2>by Points</h2>"+
					"<table><thead><tr><th>Count</th><th>Mode</th><th>Points</th></thead><tbody>";
				for (x in r.data) {
					str += 
						`<tr>`+
						`<td>${r.data[x].qso}</td>`+
						`<td>${r.data[x].cabmode}</td>`+
						`<td>${r.data[x].points}</td>`+
						`</tr>`;

					count += parseInt(r.data[x].points);

				}
				str +=
					"</tbody><tfoot>"+
					`<tr><td colspan='2' class='t'>Total:</td><td>${count}</td></tr>`+
					"</tfoot></table></div>";

				output.innerHTML += str;

			}
		},
		{
			cmd: "byclass",
			work: function(r) {
				

				
				let str =
					"<div><h2>by Class</h2>"+
					"<table><thead><tr><th>Count</th><th>Code</th><th>Name</th></thead><tbody>";
				for (x in r.data) {
					str += 
						`<tr>`+
						`<td>${r.data[x].qso}</td>`+
						`<td>${r.data[x].code}</td>`+
						`<td>${r.data[x].name}</td>`+
						`</tr>`;
				}

				str +=
					"</tbody></table></div>";
				output.innerHTML += str;
			}
		},
		{
			cmd: "byband",
			work: function(r) {
				
				let str =
					"<div><h2>by Band</h2>"+
					"<table><thead><tr><th>Count</th><th>Band</th></thead><tbody>";
				for (x in r.data) {
					if (parseInt(r.data[x].qso) == 0) continue;

					str += 
						`<tr>`+
						`<td>${r.data[x].qso}</td>`+
						`<td>${r.data[x].band}</td>`+
						`</tr>`;
				}

				str +=
					"</tbody></table></div>";
				output.innerHTML += str;

			}
		},
		{
			cmd: "byzone",
			work: function(r) {

				let no = [];

				
				let str =
					"<div><h2>by Zone</h2>"+
					"<table><thead><tr><th>Count</th><th>Code</th><th>Name</th></thead><tbody>";
				for (x in r.data) {
					if (parseInt(r.data[x].qso) == 0) {
						no.push(r.data[x].code);
						continue;
					}

					str += 
						`<tr>`+
						`<td>${r.data[x].qso}</td>`+
						`<td>${r.data[x].code}</td>`+
						`<td>${r.data[x].name}</td>`+
						`</tr>`;
				}

				str +=
					"</tbody><tfoot>"+
					"<tr><td colspan='3'>No Contacts From: "+ no.join(", ") +"</td></tr>"+
					"</tfoot></table></div>";
				output.innerHTML += str;
			}
		}
	], "summary");

});
