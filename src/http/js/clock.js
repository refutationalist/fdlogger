/* this handles the clock as well as grabbing our call and exchange */
var logclock = {
	
	ms: 250,

	time: null,
	element: null,
	tick: null,


	set: function(time) {
		logclock.time = new Date(parseFloat(time) * 1000);
	},

	start: function(element, time) {
		logclock.element = element;
		logclock.set(time);

		setInterval(logclock.draw, logclock.ms);
	},

	draw: function() {
		logclock.time = logclock.time.addMS(logclock.ms);
		logclock.element.innerHTML = logclock.time.loggedString() + ' UTC';
	}

}


document.addEventListener("DOMContentLoaded", function() { 


	interact([
		{
			cmd: "whoami" ,
			work: function(r) {
				document.getElementById("ourcall").innerHTML = r.data.call;
				document.getElementById("ourexchange").innerHTML = r.data.exchange;
			}
		},
		{ 
			cmd: "servertime",
			work: function(r) {
				logclock.start(document.getElementById("clock"), r.data);

				/* this should be given to some sort of startup watcher FIXME */
				if (document.body.classList.contains('covered'))
					document.body.classList.remove('covered');
				
			}
			
		}
	]);


	setInterval(function() {
		interact([
			{
				cmd: "servertime",
				work: function(r) {
					logclock.set(r.data);
				}
			}
		]);
	},30000);

});


