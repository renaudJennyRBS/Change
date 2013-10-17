#!/usr/bin/env bash
mkdir -p ChangeTests/UnitTestWorkspace/App/Config
cp ChangeTests/travisconfig/config/project.json ChangeTests/UnitTestWorkspace/App/Config/project.json
cp ChangeTests/travisconfig/config/project.sqlite.json ChangeTests/UnitTestWorkspace/App/Config/project.sqlite.json
sed -i 's|<current_dir>|'`pwd`'|g' ChangeTests/UnitTestWorkspace/App/Config/project.json
sed -i 's|<current_dir>|'`pwd`'|g' ChangeTests/UnitTestWorkspace/App/Config/project.sqlite.json