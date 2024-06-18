PROJNAME != ./pharbuild projname

$(PROJNAME).phar:
	./pharbuild


.PHONY: clean
clean:
	rm -f $(PROJNAME).phar


install: $(PROJNAME).phar
	mkdir -p $(DESTDIR)/usr/bin
	install -m644 $(PROJNAME).phar $(DESTDIR)/usr/bin/$(PROJNAME)

