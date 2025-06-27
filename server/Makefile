PROJNAME != ./pharbuild projname

$(PROJNAME).phar:
	./pharbuild


.PHONY: clean
clean:
	rm -f $(PROJNAME).phar


.PHONY: install_support
install_support:
	# extra config files
	mkdir -p $(DESTDIR)/usr/share/$(PROJNAME)
	install -m644 extra/lighttpd.conf-example $(DESTDIR)/usr/share/$(PROJNAME)
	install -m644 extra/logger.sql $(DESTDIR)/usr/share/$(PROJNAME)
	install -m644 extra/parse_sections.php $(DESTDIR)/usr/share/$(PROJNAME)


install: install_support $(PROJNAME).phar
	mkdir -p $(DESTDIR)/usr/share/webapps $(DESTDIR)/usr/bin $(DESTDIR)/etc
	install -m644 $(PROJNAME).phar $(DESTDIR)/usr/share/webapps
	install -m755 extra/callbookgen $(DESTDIR)/usr/bin
	install -m644 extra/logger.ini $(DESTDIR)/etc

.PHONY: dev
dev: clean $(PROJNAME).phar
	echo "If this doesn't work, create a test.ini with a proper database."
	LOGGERINI=$(shell pwd)/test.ini php -S 0.0.0.0:8000 $(PROJNAME).phar

