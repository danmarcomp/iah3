<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	sections
		--
			elements
                - name
                - iso4217
                --
                	name: conversion_rate
                	editable: "bean.id!=-99"
                - symbol
                - symbol_place_after
                - decimal_places
                --
                	name: status
                	editable: "bean.id!=-99"
                -
