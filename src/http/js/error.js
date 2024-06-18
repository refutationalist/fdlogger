
function shit(cmd = "none", input = "") {

	if (!document.body.classList.contains('error'))
		document.body.classList.add('error');


	document.getElementById("errortext").innerHTML += ` (${cmd}) ${input}`;
	console.error(`LOGGER ERROR [${cmd}]: ${input}`);
}

document.addEventListener("DOMContentLoaded", function() {
	document.querySelector("#error div.clear").addEventListener(
		"click", 
		function(evt) {
			document.getElementById("errortext").innerHTML = "";
			document.body.classList.remove('error');
		});

});
