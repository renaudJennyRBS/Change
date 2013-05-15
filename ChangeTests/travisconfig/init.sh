#!/usr/bin/env bash
mkdir -p ChangeTests/UnitTestWorkspace/App/Config
cp ChangeTests/travisconfig/config/project.default.json ChangeTests/UnitTestWorkspace/App/Config/project.default.json
cp ChangeTests/travisconfig/config/project.sqlite.json ChangeTests/UnitTestWorkspace/App/Config/project.sqlite.json
sed -i 's|<current_dir>|'`pwd`'|g' ChangeTests/UnitTestWorkspace/App/Config/project.default.json
sed -i 's|<current_dir>|'`pwd`'|g' ChangeTests/UnitTestWorkspace/App/Config/project.sqlite.json
mv ChangeTests/travisconfig/autoload/autoload.php Libraries/autoload.php
mv ChangeTests/travisconfig/autoload/composer Libraries/composer
