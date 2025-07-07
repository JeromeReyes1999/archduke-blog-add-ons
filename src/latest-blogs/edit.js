import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { Spinner } from '@wordpress/components';

export default function Edit() {
	const blockProps = useBlockProps();

	const posts = useSelect(
		(select) =>
			select(coreDataStore).getEntityRecords('postType', 'post', {
				per_page: 1,
				status: 'publish',
				_embed: true
			}),
		[]
	);

	if (!posts) {
		return <Spinner />;
	}

	if (posts.length === 0) {
		return <p {...blockProps}>No posts found.</p>;
	}

	const post = posts[0];
	const title = post.title?.rendered || '(No title)';
	const link = post.link;
	const date = new Date(post.date).toLocaleDateString();
	const rawExcerpt = post.excerpt?.rendered || '';
	const plainExcerpt = rawExcerpt.replace(/<[^>]+>/g, '').replace(/\[\&hellip;\]/g, '').trim();
	const categories =
		post._embedded?.['wp:term']?.[0]?.map((term) => term.name) || [];
	const featuredImage =
		post._embedded?.['wp:featuredmedia']?.[0]?.source_url || null;

	const readTime = post.meta?.read_time || null;
	const roundedReadTime =
		readTime !== undefined && readTime !== null
			? Math.round(parseFloat(readTime))
			: null;

	return (
		<div {...blockProps}>
			<div className="main-post">
				{/* Row 1: Featured Image */}
				<div className="row row-1">
					{featuredImage && (
						<div className="featured-img-container">
							<img
								className="featured-img"
								src={featuredImage}
								alt={title}
							/>
						</div>
					)}
				</div>

				{/* Row 2: Categories + Title, Date, Excerpt */}
				<div className="row row-2">
					<div className="col categories">
						<ul className="category-badges has-text-color has-superbfont-xxsmall-font-size">
							{categories.map((cat, i) => (
								<li className="category-badge">{cat}</li>
							))}
						</ul>
					</div>

					<div className="col content">
						<h2 className="wp-block-heading title has-text-align-left has-primary-color has-superbfont-large-font-size">
							<a href={link} target="_blank" rel="noopener noreferrer">
								{title}
							</a>
						</h2>
						<div className="date has-text-align-left has-mono-2-color has-text-color has-superbfont-xsmall-font-size">{date}</div>
						{roundedReadTime !== null && (
							<p className="read-time has-text-align-left has-mono-2-color has-text-color has-superbfont-xsmall-font-size">
								{roundedReadTime < 1 ? '< 1' : roundedReadTime} min read
							</p>
						)}
						<div className="excerpt">
							<p className="has-text-align-left has-mono-2-color has-text-color has-superbfont-xsmall-font-size">
								{plainExcerpt}{'... '}
								<a
									className="read-more-link"
									href={link}
									target="_blank"
									rel="noopener noreferrer"
								>
									Read more →
								</a>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
}