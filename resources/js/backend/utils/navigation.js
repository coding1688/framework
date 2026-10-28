export const flatten = (nodes = []) => nodes.flatMap(node => [node, ...flatten(node.children || [])]);
