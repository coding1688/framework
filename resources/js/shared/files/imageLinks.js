// Preview URLs are display-only. Registered images are submitted by stable ID.
export function imagePreviewUrl(image) {
    const value = typeof image === 'string' ? image : image?.url || image?.external_url || '';
    if (/^\/(?!\/)/.test(value)) return value;
    try {
        const url = new URL(value);
        return ['http:', 'https:', 'blob:'].includes(url.protocol) ? value : '';
    } catch {
        return '';
    }
}

export function productImageForm(record) {
    const galleries = record.gallery_images ?? (record.gallery_urls || []).map(external_url => ({ external_url }));
    return {
        cover_file_id: record.cover_file_id || null,
        cover_url: imagePreviewUrl(record.cover_url),
        gallery_images: galleries.map((image, index) => ({ ...image, url: imagePreviewUrl(image) || imagePreviewUrl(record.gallery_urls?.[index]) })),
    };
}

export function coverImagePayload({ cover_file_id, cover_url }) {
    return {
        cover_file_id: cover_file_id || null,
        external_cover_url: cover_file_id ? null : cover_url?.trim() || null,
    };
}

export function galleryImagePayload(images = []) {
    return images.filter(image => image.file_id || image.external_url?.trim())
        .map(image => image.file_id ? { file_id: image.file_id } : { external_url: image.external_url.trim() });
}

export function productCoverImagePayload({ cover_file_id, cover_url }) {
    return cover_file_id ? { cover_file_id } : { cover_url: cover_url?.trim() || null };
}
