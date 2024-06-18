#!/bin/bash

exec curl -H "Content-Type: application/json" \
	--data @$1 "http://localhost:8000/logger/api?a=$2"
