#!/bin/bash
IMAGE_NAME='radio_dns'

echo "$DOCKER_TOKEN" | docker login -u "$DOCKER_USERNAME" --password-stdin
docker build -t $IMAGE_NAME .
docker images

cat VERSION | while read TAG; do
	if [[ $TAG =~ ^#.* ]] ; then 
		echo "Skipping $TAG";
	else 
		echo "Tagging Image as $TAG and pushing";
		docker tag $IMAGE_NAME "kimbtechnologies/$IMAGE_NAME:$TAG"
      	docker push "kimbtechnologies/$IMAGE_NAME:$TAG"
	fi
done