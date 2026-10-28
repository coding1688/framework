export function language(locale) { const url=new URL(location.href); url.searchParams.set('lang',locale); location.assign(url); }
