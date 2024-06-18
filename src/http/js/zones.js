document.addEventListener("DOMContentLoaded", function(evt) {


	interact([
		{
			cmd: "zones",
			arg: [ true ],
			work: function(r) {
				let str, label;
				let output = document.getElementById('summary');
				console.log(r);

				for (x in r.data) {

					switch (x) {
						case 'C':
							label = "Canada";
							break;

						case 'X':
							continue;
							break;

						default:
							label = "US Zone " + x;
							break;
					}

					str = `<div><h2>${label}</h2><ul>`;

					for (y in r.data[x]) {
						str += `<li><span>${r.data[x][y][0]}</span> &mdash; ${r.data[x][y][1]}</li>`;
					}

					str += '</ul></div>';

					output.innerHTML += str;

				}
			}
		}
	]);

});
