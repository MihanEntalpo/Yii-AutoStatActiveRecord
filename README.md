Yii-AutoStatActiveRecord
========================

ActiveRecord which automatically adds STAT relations for each HAS_MANY and HAS_ONE

How to use it:
--------------

Just extend this class by your model, your relations would be automatically enhanced:

1. Your model class:

    class User extends AutoStatActiveRecord
    {
      // ...... stuff
      public function relations()
      {
        return array(
          'posts' => array(self::HAS_MANY, 'Post', 'author_user_id'),
          'friends' => array(self::MANY_MANY, 'User', '{{user_user_link_table}}(user_id,friend_id)'),
        );
      }
      // ...... stuff
    }
    
2. Example code:

    //Lets read user from DB:
    $user = User::model()->findByPk(11);
    
    //Counting related items with 'count' function (Just for example! Don't do it at home!)
    $friends_bad_practice = count($user->friends);
    $posts_bad_practive = count($user->posts);
    
    //Counting related items with automatic generated relation:
    $frients_good_practice = $user->friends_AUTOSTAT;
    $posts_good_practice = $user->posts_AUTOSTAT;
    
    //Counting related items with function call:
    $friends_the_best_practice = $user->countRelation('friends');
    $posts_the_best_practice = $user->countRelation('posts');
