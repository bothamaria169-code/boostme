<?php $title = ucfirst($page); ?>
<div class="card">
<?php if ($page === 'privacy'): ?>
  <h1>Privacy policy</h1>
  <p>BoostMe (a Swift Fusion service) schedules and publishes social media posts for its clients.</p>
  <p><b>What we store:</b> your name, email and password (hashed); the photos, videos and captions you upload; and the access tokens Facebook, Instagram and TikTok give us when you connect an account. We never see or store your social media passwords.</p>
  <p><b>How we use it:</b> only to publish the posts you or your account manager schedule, and to email you a weekly summary. We do not sell or share your data.</p>
  <p><b>Deleting your data:</b> disconnect an account at any time from your dashboard, or email us to delete your account and all its content within 30 days. You can also remove BoostMe from your Facebook or TikTok settings.</p>
  <p>Contact: info@swiftfusion.co.za</p>
<?php elseif ($page === 'terms'): ?>
  <h1>Terms of service</h1>
  <p>By using BoostMe you confirm you own or have the right to publish the content you upload, and that you are allowed to manage the social media accounts you connect.</p>
  <p>Posts are published through the official Facebook, Instagram and TikTok APIs and are subject to those platforms' rules. We are not responsible for content a platform removes or rejects.</p>
  <p>Either party can end the service at any time. Contact: info@swiftfusion.co.za</p>
<?php else: ?>
  <h1>Page not found</h1><p><a href="<?= e(url('dashboard')) ?>">Back to the dashboard</a></p>
<?php endif; ?>
</div>
