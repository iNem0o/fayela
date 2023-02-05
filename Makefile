DOCKER_IMAGENAME ?= inem0o/fayela

build:
	docker buildx build --tag ${DOCKER_IMAGENAME} .

push:
	docker push ${DOCKER_IMAGENAME}