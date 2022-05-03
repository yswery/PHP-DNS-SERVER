#!/bin/bash
IMAGE_NAME='radio_dns'

# https://stackoverflow.com/a/4774063
SCRIPTPATH="$(cd -- "$(dirname "$0")" >/dev/null 2>&1; pwd -P)"

echo "$DOCKER_TOKEN" | docker login -u "$DOCKER_USERNAME" --password-stdin

docker build "$SCRIPTPATH/../" \
	--file "$SCRIPTPATH/Dockerfile" \
	--tag $IMAGE_NAME
docker images

cat "$SCRIPTPATH/../VERSION" | while read TAG; do
	if [[ $TAG =~ ^#.* ]] ; then 
		echo "Skipping $TAG";
	else 
		echo "Tagging Image as $TAG and pushing";
		docker tag $IMAGE_NAME "kimbtechnologies/$IMAGE_NAME:$TAG"
      	docker push "kimbtechnologies/$IMAGE_NAME:$TAG"
	fi
done