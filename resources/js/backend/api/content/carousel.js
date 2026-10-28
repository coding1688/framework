import { request } from '@/utils/request';

export const carouselApi = {
    list: options => request('content.carousel.list', options),
    detail: options => request('content.carousel.detail', options),
    create: options => request('content.carousel.create', options),
    update: options => request('content.carousel.update', options),
    status: options => request('content.carousel.status', options),
    delete: options => request('content.carousel.delete', options),
    options: options => request('content.carousel.options', options),
    upload: options => request('content.carousel.upload', options),
};
