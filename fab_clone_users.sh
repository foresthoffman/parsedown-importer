#!/bin/sh

role="administrator"
fname="Admin"
for (( i = 1; i < 6; i++ )); do
	flast="Mc$fname I"
	for (( x = 1; x < $i; x++ )); do
		flast+="I"
	done
	wp user create $role$i $role$i@ex.com --role=$role --user_pass=trolls --first_name=$fname --last_name="$flast";
done