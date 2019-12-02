/*
 * Copyright (C) 2019 Gigadrive - All rights reserved.
 * https://gigadrivegroup.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://gnu.org/licenses/>
 */

const merge = require("webpack-merge");
const common = require("./webpack.common");
const {resolve} = require("path");

module.exports = merge(common, {
	mode: "development",
	watch: true,
	output: {
		filename: "bundle.js",
		path: resolve(__dirname, "public/build/")
	},
	module: {
		rules: [
			{
				test: /\.css$/,
				use: [
					{loader: "style-loader"},
					{loader: "css-loader"}
				]
			},
			{
				test: /\.scss$/,
				use: [
					{loader: "style-loader"},
					{loader: "css-loader"},
					{
						loader: "sass-loader", options: {
							includePaths: [
								resolve(__dirname, "node_modules")
							]
						}
					}
				]
			},
			{
				test: /\.less$/,
				use: [
					{
						loader: "style-loader"
					},
					{
						loader: "css-loader"
					},
					{
						loader: "less-loader"
					}
				]
			},
		]
	}
});