
class logrecord {

	// callsign of contact: ex. K7LED
	call   = null;

	// this is the full exchange: 1D-WWA
	tx     = null;
	clss   = null;
	zone   = null;

	// any notes the person might have
	notes  = null;

	// not added by the parser: frequency and mode
	mode   = null;
	freq   = 0;

	// is there a dupe?
	nodupe = null;

	islog(checkdupe = false) {
		// we don't actually care about notes for completion
		if (
			this.call   != null &&
			this.clss   != null &&
			this.tx     != null &&
			this.zone   != null &&
			this.mode   != null &&
			this.freq > 0 && this.freq != null
		) {
			if (
				checkdupe == false ||
				(this.nodupe != null && this.nodupe != false) // but I repeat myself;
			) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	isnote() {
		// is this just a note?
		if (
			this.call   == null &&
			this.clss   == null &&
			this.tx     == null &&
			this.zone   == null &&
			this.notes  != null
		) {
			return true;
		} else {
			return false;
		}
	}

	str() {
		return this.call +
			this.tx    +
			this.clss  +
			this.zone  +
			this.mode  +
			this.notes +
			this.freq;
	}
};



class parse {


	static regex =  {
		call:     /\b([vwank][a-z]?[0-9][a-z]{1,5})(\/\w+)?\b/i,
		exchange: /\b([0-9]+)([a-f]b?)\b/i,
		section:  /\b([a-z]{2,3})\b/i,
		dxcall:   /\b([a-z0-9]{1,3}[0-9][a-z0-9]{0,3}[a-z])\b/i,
		notes:    /\[(.*)\]/i
	}


	static go(incoming) {
		let parsed = new logrecord();

		// notes
		let n = this.regex.notes.exec(incoming);
		if (n) {
			parsed.notes = n[1];
			incoming = incoming.replace(this.regex.notes, "");
		}

		// callsign
		let findcall = this.regex.call.exec(incoming);
		if (findcall) parsed.call = findcall[1].toUpperCase();

		// class and tx
		let txcls = this.regex.exchange.exec(incoming);
		if (txcls) {
			parsed.tx   = parseInt(txcls[1]);
			parsed.clss = txcls[2].toUpperCase();
		}

		// zone
		let findsec = this.regex.section.exec(incoming);
		if (findsec) {
			parsed.zone = findsec[1].toUpperCase();

			if (parsed.zone == "DX" && parsed.call == null) {
				let finddx = this.regex.dxcall.exec(incoming);
				if (finddx) parsed.call = finddx[1].toUpperCase();
			}

		}

		return parsed;

	}
}


