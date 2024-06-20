
install:
		mkdir -p \
			$(DESTDIR)/usr/bin \
			$(DESTDIR)/usr/share/loggerscripts \
			$(DESTDIR)/usr/share/applications \
			$(DESTDIR)/usr/share/icons/loggerscripts

		install -m644 default.fielddayrc $(DESTDIR)/usr/share/loggerscripts
		install -m644 fieldday.sh        $(DESTDIR)/usr/share/loggerscripts
		install -m644 icons/*			 $(DESTDIR)/usr/share/icons/loggerscripts
		install -m644 desktop/*			 $(DESTDIR)/usr/share/applications

		install -m755 loggerscripts-follow		$(DESTDIR)/usr/bin
		install -m755 loggerscripts-rigctld		$(DESTDIR)/usr/bin
		install -m755 loggerscripts-wsjtx		$(DESTDIR)/usr/bin

			
