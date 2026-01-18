/**
 * Convert a ProseMirror selection to Markdown, preserving both block structure and inline marks.
 *
 * @param {Object} doc - The ProseMirror document
 * @param {number} from - Selection start position
 * @param {number} to - Selection end position
 * @returns {string} - Markdown representation of the selection
 */
export function selectionToMarkdown(doc, from, to) {
    const blocks = [];
    let currentBlock = null;
    let currentBlockType = null;
    let currentBlockAttrs = null;

    doc.nodesBetween(from, to, (node, pos) => {
        // Handle block-level nodes
        if (node.isBlock && !node.isTextblock) {
            // Container nodes like doc, blockquote, lists - descend into them
            return true;
        }

        if (node.isTextblock) {
            // Save previous block if exists
            if (currentBlock !== null) {
                blocks.push({
                    type: currentBlockType,
                    attrs: currentBlockAttrs,
                    content: currentBlock,
                });
            }

            // Start new block
            currentBlockType = node.type.name;
            currentBlockAttrs = { ...node.attrs };
            currentBlock = '';

            // Process text content of this block
            node.content.forEach((child, offset) => {
                if (child.isText) {
                    const childPos = pos + 1 + offset;
                    const childEnd = childPos + child.nodeSize;

                    // Check if this text node is within selection
                    if (childEnd > from && childPos < to) {
                        let text = child.text;

                        // Get the portion of text within selection
                        const textStart = Math.max(0, from - childPos);
                        const textEnd = Math.min(child.nodeSize, to - childPos);
                        text = text.slice(textStart, textEnd);

                        // Apply marks
                        text = applyMarks(text, child.marks);

                        currentBlock += text;
                    }
                }
            });

            return false; // Don't descend into textblock children (we handled them)
        }

        return true;
    });

    // Don't forget the last block
    if (currentBlock !== null) {
        blocks.push({
            type: currentBlockType,
            attrs: currentBlockAttrs,
            content: currentBlock,
        });
    }

    // Convert blocks to markdown
    return blocks.map(block => blockToMarkdown(block)).join('\n\n');
}

/**
 * Apply inline marks to text content.
 */
function applyMarks(text, marks) {
    if (!marks || marks.length === 0) {
        return text;
    }

    // Apply marks in order: code first (innermost), then bold/italic/strike, then link (outermost)

    // Check for code mark first (innermost)
    if (marks.some(m => m.type.name === 'code')) {
        text = `\`${text}\``;
    }

    // Apply bold
    if (marks.some(m => m.type.name === 'bold')) {
        text = `**${text}**`;
    }

    // Apply italic
    if (marks.some(m => m.type.name === 'italic')) {
        text = `_${text}_`;
    }

    // Apply strikethrough
    if (marks.some(m => m.type.name === 'strike')) {
        text = `~~${text}~~`;
    }

    // Apply link (outermost)
    const linkMark = marks.find(m => m.type.name === 'link');
    if (linkMark) {
        text = `[${text}](${linkMark.attrs.href})`;
    }

    return text;
}

/**
 * Convert a block to its markdown representation.
 */
function blockToMarkdown(block) {
    const { type, attrs, content } = block;

    switch (type) {
        case 'heading': {
            const level = attrs.level || 1;
            const prefix = '#'.repeat(level) + ' ';
            return prefix + content;
        }
        case 'paragraph':
            return content;
        case 'codeBlock': {
            const lang = attrs.language || '';
            return '```' + lang + '\n' + content + '\n```';
        }
        case 'blockquote':
            return '> ' + content;
        case 'listItem':
            return '- ' + content;
        default:
            return content;
    }
}
