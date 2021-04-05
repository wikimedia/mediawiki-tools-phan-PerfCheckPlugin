#!/bin/bash
# Author: Bawolff
# ==================================

dir=`dirname $0`
cd "$dir/../"
progressBar=""
# This doesn't really work from composer
if [ -t 2 ]
	then progressBar="-p"
fi

echo Starting phan

php "./vendor/phan/phan/phan" \
        --project-root-directory . \
        --allow-polyfill-parser \
        --config-file "tests/self-phan-config.php" \
        --output "php://stdout" \
        $progressBar $@
result=$?
echo
exit $result
