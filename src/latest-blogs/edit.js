/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */

import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { Spinner } from '@wordpress/components';

export default function Edit() {
	const blockProps = useBlockProps();

	const posts = useSelect(
		(select) =>
			select(coreDataStore).getEntityRecords('postType', 'post', {
				per_page: -1,
				status: 'publish',
			}),
		[]
	);

	if (!posts) {
		return <Spinner />;
	}

	if (posts.length === 0) {
		return <p {...blockProps}>No posts found.</p>;
	}

	return (
		<ul {...blockProps}>
			{posts.map((post) => (
				<li key={post.id}>
					<a href={post.link} target="_blank" rel="noopener noreferrer">
						{post.title.rendered || '(No title)'}
					</a>
				</li>
			))}
		</ul>
	);
}
